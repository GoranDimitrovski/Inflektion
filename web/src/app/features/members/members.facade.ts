import { inject, Injectable, signal } from '@angular/core';
import {
  v1InvitationsDestroy,
  v1InvitationsIndex,
  v1InvitationsStore,
  v1MembershipsDestroy,
  v1MembershipsIndex,
  v1MembershipsUpdate,
} from '../../api/sdk.gen';
import type { V1InvitationsIndexResponses, V1MembershipsIndexResponses } from '../../api/types.gen';
import { SessionService } from '../../core/session';
import { firstApiError } from '../../shared/api-error';

export type MembershipResource = V1MembershipsIndexResponses[200]['data'][number];
export type InvitationResource = V1InvitationsIndexResponses[200]['data'][number];

@Injectable()
export class MembersFacade {
  readonly memberships = signal<MembershipResource[]>([]);
  readonly invitations = signal<InvitationResource[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  private readonly session = inject(SessionService);

  /** Route-scoped: the `accounts/:accountId` guard has already resolved the account these calls belong to. */
  private account(): string {
    return String(this.session.requireAccountId());
  }

  async load(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    const [members, pending] = await Promise.all([
      v1MembershipsIndex({ path: { account: this.account() } }),
      v1InvitationsIndex({ path: { account: this.account() } }),
    ]);

    if (members.error || pending.error) {
      this.error.set(firstApiError(members.error ?? pending.error, 'Failed to load members.'));
    } else {
      this.memberships.set(members.data?.data ?? []);
      this.invitations.set(pending.data?.data ?? []);
    }

    this.loading.set(false);
  }

  async invite(email: string, role: string): Promise<string | null> {
    const { data, error } = await v1InvitationsStore({
      path: { account: this.account() },
      body: { data: { type: 'invitations', attributes: { email, role } } },
    });

    if (error) {
      return firstApiError(error, 'Failed to send the invitation.');
    }

    if (data) {
      this.invitations.update((invitations) => [data.data, ...invitations]);
    }

    return null;
  }

  async revokeInvitation(invitationId: string): Promise<boolean> {
    const { error } = await v1InvitationsDestroy({
      path: { account: this.account(), invitation: invitationId },
    });

    if (error) {
      return false;
    }

    this.invitations.update((invitations) => invitations.filter((invitation) => invitation.id !== invitationId));

    return true;
  }

  async changeRole(membershipId: string, role: string): Promise<string | null> {
    const { data, error } = await v1MembershipsUpdate({
      path: { account: this.account(), membership: membershipId },
      body: { data: { type: 'memberships', id: membershipId, attributes: { role } } },
    });

    if (error) {
      return firstApiError(error, 'Failed to change the role.');
    }

    if (data) {
      this.memberships.update((memberships) =>
        memberships.map((membership) => (membership.id === membershipId ? data.data : membership)),
      );
    }

    return null;
  }

  async revokeMembership(membershipId: string): Promise<string | null> {
    const { error } = await v1MembershipsDestroy({
      path: { account: this.account(), membership: membershipId },
    });

    if (error) {
      return firstApiError(error, 'Failed to remove this member.');
    }

    this.memberships.update((memberships) => memberships.filter((membership) => membership.id !== membershipId));

    return null;
  }
}
