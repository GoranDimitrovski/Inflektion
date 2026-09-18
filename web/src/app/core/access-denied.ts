import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

/** Where `requirePermission` sends a member whose role doesn't cover the route. */
@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink],
  selector: 'app-access-denied',
  styleUrl: '../features/auth/auth-form.scss',
  template: `
    <section>
      <h1>Access denied</h1>
      <p>Your role in this account doesn't include this page. Ask an owner or admin if you need it.</p>
      <p><a routerLink="/">Back to your accounts</a></p>
    </section>
  `,
})
export class AccessDenied {}
