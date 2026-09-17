import { ChangeDetectionStrategy, Component, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { Router, RouterLink } from '@angular/router';
import { SessionService } from '../../core/session';

interface LoginModel {
  email: string;
  password: string;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormField, RouterLink],
  selector: 'app-login-page',
  styleUrl: './auth-form.scss',
  templateUrl: './login-page.html',
})
export class LoginPage {
  private readonly session = inject(SessionService);
  private readonly router = inject(Router);

  protected readonly model = signal<LoginModel>({ email: '', password: '' });

  protected readonly loginForm = form(this.model, (path) => {
    required(path.email, { message: 'Email is required.' });
    required(path.password, { message: 'Password is required.' });
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
        this.error.set('Invalid email or password.');

        return;
      }

      const firstAccountId = this.session.memberships()[0]?.account.id;

      await this.router.navigate(
        firstAccountId ? ['/accounts', firstAccountId, 'programs'] : ['/'],
      );
    });

    this.submitting.set(false);
  }
}
