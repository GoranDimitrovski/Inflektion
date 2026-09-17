import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { SessionService } from './session';

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  selector: 'app-account-shell',
  styleUrl: './account-shell.scss',
  templateUrl: './account-shell.html',
})
export class AccountShell {
  protected readonly session = inject(SessionService);
  private readonly router = inject(Router);

  protected async onLogout(): Promise<void> {
    await this.session.logout();
    await this.router.navigate(['/login']);
  }
}
