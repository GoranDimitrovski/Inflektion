import { ChangeDetectionStrategy, Component, OnInit, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { invitationsAccept, invitationsShow } from '../../api/sdk.gen';
import { SessionService } from '../../core/session';

interface RegisterModel {
  name: string;
  password: string;
  password_confirmation: string;
}

interface InvitationSummary {
  accountName: string;
  email: string;
  role: string;
  userExists: boolean;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormField, RouterLink],
  selector: 'app-accept-invitation-page',
  styleUrl: '../auth/auth-form.scss',
  templateUrl: './accept-invitation-page.html',
})
export class AcceptInvitationPage implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  protected readonly session = inject(SessionService);

  protected readonly invitation = signal<InvitationSummary | null>(null);
  protected readonly notFound = signal(false);
  protected readonly submitting = signal(false);
  protected readonly error = signal<string | null>(null);

  protected readonly registerModel = signal<RegisterModel>({
    name: '',
    password: '',
    password_confirmation: '',
  });
  protected readonly registerForm = form(this.registerModel, (path) => {
    required(path.name, { message: 'Name is required.' });
    required(path.password, { message: 'Password is required.' });
  });

  private token = '';

  async ngOnInit(): Promise<void> {
    this.token = this.route.snapshot.paramMap.get('token') ?? '';

    const { data, error } = await invitationsShow({ path: { token: this.token } });

    if (error || !data) {
      this.notFound.set(true);

      return;
    }

    this.invitation.set(data.data);
  }

  protected async onAcceptAsSelf(): Promise<void> {
    await this.accept({});
  }

  protected async onRegisterAndAccept(): Promise<void> {
    this.submitting.set(true);
    this.error.set(null);

    await submit(this.registerForm, async () => {
      await this.accept(this.registerModel());
    });

    this.submitting.set(false);
  }

  private async accept(body: Partial<RegisterModel>): Promise<void> {
    this.submitting.set(true);
    this.error.set(null);

    const { data, error } = await invitationsAccept({
      path: { token: this.token },
      // Generated type marks name/password as always-required (Scramble merged
      // two validation branches); both are actually optional per request.
      body: body as never,
    });

    this.submitting.set(false);

    if (error) {
      this.error.set(
        (error as { errors?: { detail?: string }[]; message?: string }).errors?.[0]?.detail ??
          (error as { message?: string }).message ??
          'Failed to accept this invitation.',
      );

      return;
    }

    if (data) {
      await this.session.load();
      await this.router.navigate(['/accounts', data.data.account.id, 'programs']);
    }
  }
}
