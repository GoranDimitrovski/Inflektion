import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { Router } from '@angular/router';
import { accountsLeave } from '../../api/sdk.gen';
import { DataTable } from '../../shared/data-table';
import { Modal } from '../../shared/modal';
import { SessionService } from '../../core/session';
import { InvitationResource, MembersFacade, MembershipResource } from './members.facade';

interface InviteModel {
  email: string;
  role: string;
}

const ROLES = ['owner', 'admin', 'member', 'viewer'];

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [DataTable, DatePipe, FormField, Modal],
  providers: [MembersFacade],
  selector: 'app-members-page',
  styleUrl: './members-page.scss',
  templateUrl: './members-page.html',
})
export class MembersPage implements OnInit {
  protected readonly facade = inject(MembersFacade);
  protected readonly session = inject(SessionService);
  private readonly router = inject(Router);

  protected readonly roles = ROLES;
  protected readonly tab = signal<'members' | 'invitations'>('members');
  protected readonly confirmingRemovalOf = signal<string | null>(null);
  protected readonly showInviteModal = signal(false);

  protected readonly inviteModel = signal<InviteModel>({ email: '', role: 'member' });
  protected readonly inviteForm = form(this.inviteModel, (path) => {
    required(path.email, { message: 'Email is required.' });
    required(path.role, { message: 'Role is required.' });
  });
  protected readonly inviting = signal(false);
  protected readonly inviteError = signal<string | null>(null);

  protected readonly leaving = signal(false);
  protected readonly leaveError = signal<string | null>(null);

  ngOnInit(): void {
    // Listing requires members.manage; a plain Member/Viewer can still reach
    // this page to leave the account, so the load is skipped for them.
    if (this.canManage()) {
      void this.facade.load(this.accountId());
    }
  }

  protected canManage(): boolean {
    return this.session.can('members.manage');
  }

  protected async onLeave(): Promise<void> {
    this.leaving.set(true);
    this.leaveError.set(null);

    const { error } = await accountsLeave({ path: { account: String(this.accountId()) } });

    if (error) {
      this.leaveError.set(
        (error as { errors?: { detail?: string }[] }).errors?.[0]?.detail ??
          'Failed to leave this account.',
      );
      this.leaving.set(false);

      return;
    }

    await this.session.load();
    await this.router.navigate(['/']);
  }

  protected isSelf(membership: MembershipResource): boolean {
    return membership.attributes.userEmail === this.session.user()?.email;
  }

  protected async onInvite(): Promise<void> {
    this.inviting.set(true);
    this.inviteError.set(null);

    await submit(this.inviteForm, async () => {
      const error = await this.facade.invite(
        this.accountId(),
        this.inviteModel().email,
        this.inviteModel().role,
      );

      if (error) {
        this.inviteError.set(error);
      } else {
        this.inviteModel.set({ email: '', role: 'member' });
        this.showInviteModal.set(false);
      }
    });

    this.inviting.set(false);
  }

  protected async onChangeRole(membership: MembershipResource, role: string): Promise<void> {
    await this.facade.changeRole(this.accountId(), membership.id, role);
  }

  protected async onRevokeInvitation(invitation: InvitationResource): Promise<void> {
    await this.facade.revokeInvitation(this.accountId(), invitation.id);
  }

  /** Dangerous action: requires typing the member's email before it fires — see the project's "typed confirmation" rule. */
  protected confirmRemoval(membership: MembershipResource, typedEmail: string): void {
    if (typedEmail.trim().toLowerCase() === membership.attributes.userEmail.toLowerCase()) {
      void this.facade
        .revokeMembership(this.accountId(), membership.id)
        .then(() => this.confirmingRemovalOf.set(null));
    }
  }

  private accountId(): number {
    const accountId = this.session.activeMembership()?.account.id;

    if (accountId === undefined) {
      throw new Error(
        'MembersPage rendered without an active account — the route guard should prevent this.',
      );
    }

    return accountId;
  }
}
