import { ChangeDetectionStrategy, Component, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { Router, RouterLink } from '@angular/router';
import { SessionService } from '../../core/session';

interface RegisterModel {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  accountName: string;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormField, RouterLink],
  selector: 'app-register-page',
  styleUrl: './auth-form.scss',
  templateUrl: './register-page.html',
})
export class RegisterPage {
  private readonly session = inject(SessionService);
  private readonly router = inject(Router);

  protected readonly model = signal<RegisterModel>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    accountName: '',
  });

  protected readonly registerForm = form(this.model, (path) => {
    required(path.name, { message: 'Name is required.' });
    required(path.email, { message: 'Email is required.' });
    required(path.password, { message: 'Password is required.' });
    required(path.password_confirmation, { message: 'Please confirm your password.' });
    required(path.accountName, { message: 'Account name is required.' });
  });

  protected readonly submitting = signal(false);
  protected readonly error = signal<string | null>(null);

  protected async onSubmit(): Promise<void> {
    this.submitting.set(true);
    this.error.set(null);

    await submit(this.registerForm, async () => {
      const success = await this.session.register(this.model());

      if (!success) {
        this.error.set('Could not create your account. Check your details and try again.');

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
