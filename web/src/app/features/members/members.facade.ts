import { Injectable, signal } from '@angular/core';
import {
  v1InvitationsDestroy,
  v1InvitationsIndex,
  v1InvitationsStore,
  v1MembershipsDestroy,
  v1MembershipsIndex,
  v1MembershipsUpdate,
} from '../../api/sdk.gen';
import type { V1InvitationsIndexResponses, V1MembershipsIndexResponses } from '../../api/types.gen';
import { firstApiError } from '../../shared/api-error';

export type MembershipResource = V1MembershipsIndexResponses[200]['data'][number];
export type InvitationResource = V1InvitationsIndexResponses[200]['data'][number];


@Injectable()
export class MembersFacade {
  readonly memberships = signal<MembershipResource[]>([]);
  readonly invitations = signal<InvitationResource[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  async load(accountId: number): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    const [members, pending] = await Promise.all([
      v1MembershipsIndex({ path: { account: String(accountId) } }),
      v1InvitationsIndex({ path: { account: String(accountId) } }),
    ]);

    if (members.error || pending.error) {
      this.error.set(firstApiError(members.error ?? pending.error, 'Failed to load members.'));
    } else {
      this.memberships.set(members.data?.data ?? []);
      this.invitations.set(pending.data?.data ?? []);
    }

    this.loading.set(false);
  }

  async invite(accountId: number, email: string, role: string): Promise<string | null> {
    const { data, error } = await v1InvitationsStore({
      path: { account: String(accountId) },
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

  async revokeInvitation(accountId: number, invitationId: string): Promise<boolean> {
    const { error } = await v1InvitationsDestroy({
      path: { account: String(accountId), invitation: invitationId },
    });

    if (error) {
      return false;
    }

    this.invitations.update((invitations) => invitations.filter((invitation) => invitation.id !== invitationId));

    return true;
  }

  async changeRole(accountId: number, membershipId: string, role: string): Promise<string | null> {
    const { data, error } = await v1MembershipsUpdate({
      path: { account: String(accountId), membership: membershipId },
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

  async revokeMembership(accountId: number, membershipId: string): Promise<string | null> {
    const { error } = await v1MembershipsDestroy({
      path: { account: String(accountId), membership: membershipId },
    });

    if (error) {
      return firstApiError(error, 'Failed to remove this member.');
    }

    this.memberships.update((memberships) => memberships.filter((membership) => membership.id !== membershipId));

    return null;
  }
}
