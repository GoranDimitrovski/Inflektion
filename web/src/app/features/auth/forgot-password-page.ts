import { ChangeDetectionStrategy, Component, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { RouterLink } from '@angular/router';
import { SessionService } from '../../core/session';

interface ForgotPasswordModel {
  email: string;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormField, RouterLink],
  selector: 'app-forgot-password-page',
  styleUrl: './auth-form.scss',
  templateUrl: './forgot-password-page.html',
})
export class ForgotPasswordPage {
  private readonly session = inject(SessionService);

  protected readonly model = signal<ForgotPasswordModel>({ email: '' });

  protected readonly requestForm = form(this.model, (path) => {
    required(path.email, { message: 'Email is required.' });
  });

  protected readonly submitting = signal(false);
  protected readonly submitted = signal(false);

  protected async onSubmit(): Promise<void> {
    this.submitting.set(true);

    await submit(this.requestForm, async () => {
      // Always show success regardless of outcome — avoids account enumeration.
      await this.session.requestPasswordReset(this.model().email);
      this.submitted.set(true);
    });

    this.submitting.set(false);
  }
}
