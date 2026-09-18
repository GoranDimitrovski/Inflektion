import { ChangeDetectionStrategy, Component, OnInit, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { Router, RouterLink } from '@angular/router';
import { SessionService } from '../../core/session';

interface LoginModel {
  email: string;
  password: string;
}

interface ChallengeModel {
  code: string;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormField, RouterLink],
  selector: 'app-login-page',
  styleUrl: './auth-form.scss',
  templateUrl: './login-page.html',
})
export class LoginPage implements OnInit {
  protected readonly session = inject(SessionService);
  private readonly router = inject(Router);

  ngOnInit(): void {
    // Reached while already signed in (the root path redirects here) — go
    // straight through instead of showing the form again.
    if (this.session.memberships().length > 0) {
      void this.navigateToFirstAccount();
    }
  }

  protected readonly model = signal<LoginModel>({ email: '', password: '' });

  protected readonly loginForm = form(this.model, (path) => {
    required(path.email, { message: 'Email is required.' });
    required(path.password, { message: 'Password is required.' });
  });

  protected readonly challengeModel = signal<ChallengeModel>({ code: '' });

  protected readonly challengeForm = form(this.challengeModel, (path) => {
    required(path.code, { message: 'Enter your authentication code.' });
  });

  protected readonly submitting = signal(false);
  protected readonly error = signal<string | null>(null);

  protected async onSubmit(): Promise<void> {
    this.submitting.set(true);
    this.error.set(null);

    await submit(this.loginForm, async () => {
      const { email, password } = this.model();
      const success = await this.session.login(email, password);

      if (!success) {
        if (!this.session.twoFactorPending()) {
          this.error.set('Invalid email or password.');
        }

        return;
      }

      await this.navigateToFirstAccount();
    });

    this.submitting.set(false);
  }

  protected async onSubmitChallenge(): Promise<void> {
    this.submitting.set(true);
    this.error.set(null);

    await submit(this.challengeForm, async () => {
      // Recovery codes are generated as "xxxxxxxxxx-xxxxxxxxxx" (see Fortify's
      // RecoveryCode::generate) — a TOTP code never contains a hyphen.
      const value = this.challengeModel().code.trim();
      const payload = value.includes('-') ? { recovery_code: value } : { code: value };
      const success = await this.session.twoFactorChallenge(payload);

      if (!success) {
        this.error.set('That code is invalid or has expired.');

        return;
      }

      await this.navigateToFirstAccount();
    });

    this.submitting.set(false);
  }

  private async navigateToFirstAccount(): Promise<void> {
    const firstAccountId = this.session.memberships()[0]?.account.id;

    await this.router.navigate(firstAccountId ? ['/accounts', firstAccountId, 'programs'] : ['/']);
  }
}
