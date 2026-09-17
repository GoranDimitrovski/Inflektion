import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { SessionService } from '../../core/session';

interface ResetPasswordModel {
  password: string;
  password_confirmation: string;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormField, RouterLink],
  selector: 'app-reset-password-page',
  styleUrl: './auth-form.scss',
  templateUrl: './reset-password-page.html',
})
export class ResetPasswordPage {
  private readonly route = inject(ActivatedRoute);
  private readonly session = inject(SessionService);
  private readonly router = inject(Router);

  // The password reset email links here with these as query params — see
  // ResetPassword::createUrlUsing in the backend's AppServiceProvider.
  private readonly token = this.route.snapshot.queryParamMap.get('token') ?? '';
  private readonly email = this.route.snapshot.queryParamMap.get('email') ?? '';

  protected readonly model = signal<ResetPasswordModel>({ password: '', password_confirmation: '' });

  protected readonly resetForm = form(this.model, (path) => {
    required(path.password, { message: 'Password is required.' });
    required(path.password_confirmation, { message: 'Please confirm your password.' });
  });

  protected readonly submitting = signal(false);
  protected readonly error = signal<string | null>(null);

  protected async onSubmit(): Promise<void> {
    this.submitting.set(true);
    this.error.set(null);

    await submit(this.resetForm, async () => {
      const success = await this.session.resetPassword({
        token: this.token,
        email: this.email,
        ...this.model(),
      });

      if (!success) {
        this.error.set('That reset link is invalid or has expired.');

        return;
      }

      await this.router.navigate(['/login']);
    });

    this.submitting.set(false);
  }
}
