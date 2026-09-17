import { computed, Injectable, signal } from '@angular/core';
import { client } from '../api/client.gen';

export interface AccountSummary {
  id: number;
  name: string;
  slug: string;
}

export interface MembershipView {
  account: AccountSummary;
  role: string;
  permissions: string[];
  twoFactorRequired: boolean;
}

export interface UserSummary {
  id: number;
  name: string;
  email: string;
  twoFactorEnabled: boolean;
}

/**
 * Permissions come pre-resolved from the server per membership — never
 * derive them from a role name client-side, that mapping would drift from
 * the backend's PermissionMap. App-wide singleton; everything else is a
 * route-scoped facade (see ProgramsFacade).
 */
@Injectable({ providedIn: 'root' })
export class SessionService {
  private readonly userSignal = signal<UserSummary | null>(null);
  private readonly membershipsSignal = signal<MembershipView[]>([]);
  private readonly activeAccountIdSignal = signal<number | null>(null);
  readonly twoFactorPending = signal(false);

  readonly isAuthenticated = computed(() => this.userSignal() !== null);
  readonly user = computed(() => this.userSignal());
  readonly memberships = computed(() => this.membershipsSignal());

  readonly activeMembership = computed(() => {
    const accountId = this.activeAccountIdSignal();

    return this.membershipsSignal().find((membership) => membership.account.id === accountId) ?? null;
  });

  readonly permissions = computed(() => this.activeMembership()?.permissions ?? []);

  can(permission: string): boolean {
    return this.permissions().includes(permission);
  }

  setActiveAccountId(accountId: number): void {
    this.activeAccountIdSignal.set(accountId);
  }

  async bootstrap(): Promise<void> {
    await this.primeCsrfCookie();
    await this.load();
  }

  async login(email: string, password: string): Promise<boolean> {
    this.twoFactorPending.set(false);

    const response = await fetch(`${this.apiOrigin()}/login`, {
      method: 'POST',
      credentials: 'include',
      headers: this.jsonHeaders(),
      body: JSON.stringify({ email, password }),
    });

    if (!response.ok) {
      return false;
    }

    // A 2FA-enabled user gets a 200 with this flag instead of the usual 204 —
    // no session exists yet until /two-factor-challenge completes it.
    if (response.status === 200) {
      const body = (await response.json()) as { data: { twoFactorRequired: boolean } };

      if (body.data.twoFactorRequired) {
        this.twoFactorPending.set(true);

        return false;
      }
    }

    await this.load();

    return true;
  }

  async twoFactorChallenge(payload: { code?: string; recovery_code?: string }): Promise<boolean> {
    const response = await fetch(`${this.apiOrigin()}/two-factor-challenge`, {
      method: 'POST',
      credentials: 'include',
      headers: this.jsonHeaders(),
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      return false;
    }

    this.twoFactorPending.set(false);
    await this.load();

    return true;
  }

  async register(payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    accountName: string;
  }): Promise<boolean> {
    const response = await fetch(`${this.apiOrigin()}/register`, {
      method: 'POST',
      credentials: 'include',
      headers: this.jsonHeaders(),
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      return false;
    }

    await this.load();

    return true;
  }

  /** Always resolves true regardless of whether the email is registered — the backend doesn't reveal that either. */
  async requestPasswordReset(email: string): Promise<boolean> {
    const response = await fetch(`${this.apiOrigin()}/forgot-password`, {
      method: 'POST',
      credentials: 'include',
      headers: this.jsonHeaders(),
      body: JSON.stringify({ email }),
    });

    return response.ok;
  }

  async resetPassword(payload: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<boolean> {
    const response = await fetch(`${this.apiOrigin()}/reset-password`, {
      method: 'POST',
      credentials: 'include',
      headers: this.jsonHeaders(),
      body: JSON.stringify(payload),
    });

    return response.ok;
  }

  async logout(): Promise<void> {
    await fetch(`${this.apiOrigin()}/logout`, {
      method: 'DELETE',
      credentials: 'include',
      headers: this.jsonHeaders(),
    });

    this.userSignal.set(null);
    this.membershipsSignal.set([]);
    this.activeAccountIdSignal.set(null);
    this.twoFactorPending.set(false);
  }

  async load(): Promise<boolean> {
    const response = await fetch(`${this.apiOrigin()}/me`, {
      credentials: 'include',
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      this.userSignal.set(null);
      this.membershipsSignal.set([]);

      return false;
    }

    const body = (await response.json()) as { data: { user: UserSummary; memberships: MembershipView[] } };

    this.userSignal.set(body.data.user);
    this.membershipsSignal.set(body.data.memberships);

    return true;
  }

  private async primeCsrfCookie(): Promise<void> {
    await fetch(`${this.webOrigin()}/sanctum/csrf-cookie`, { credentials: 'include' });
  }

  private apiOrigin(): string {
    return this.webOrigin() + '/api';
  }

  private webOrigin(): string {
    return (client.getConfig().baseUrl ?? '').replace(/\/api$/, '');
  }

  private jsonHeaders(): Record<string, string> {
    const xsrfToken = this.readCookie('XSRF-TOKEN');

    return {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(xsrfToken ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) } : {}),
    };
  }

  private readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? match[1] : null;
  }
}
