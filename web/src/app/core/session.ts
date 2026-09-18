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
  readonly user = this.userSignal.asReadonly();
  readonly memberships = this.membershipsSignal.asReadonly();

  readonly activeMembership = computed(() => {
    const accountId = this.activeAccountIdSignal();

    return this.membershipsSignal().find((membership) => membership.account.id === accountId) ?? null;
  });

  readonly permissions = computed(() => this.activeMembership()?.permissions ?? []);

  can(permission: string): boolean {
    return this.permissions().includes(permission);
  }

  /** The active account's id. Pages under `accounts/:accountId` always have one — the route guard resolves it first. */
  requireAccountId(): number {
    const accountId = this.activeMembership()?.account.id;

    if (accountId === undefined) {
      throw new Error('No active account — the accounts/:accountId route guard should have resolved one.');
    }

    return accountId;
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

    const response = await this.send('/login', { email, password });

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
    const response = await this.send('/two-factor-challenge', payload);

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
    const response = await this.send('/register', payload);

    if (!response.ok) {
      return false;
    }

    await this.load();

    return true;
  }

  /** Always resolves true regardless of whether the email is registered — the backend doesn't reveal that either. */
  async requestPasswordReset(email: string): Promise<boolean> {
    return (await this.send('/forgot-password', { email })).ok;
  }

  async resetPassword(payload: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<boolean> {
    return (await this.send('/reset-password', payload)).ok;
  }

  async logout(): Promise<void> {
    await this.send('/logout', undefined, 'DELETE');

    this.userSignal.set(null);
    this.membershipsSignal.set([]);
    this.activeAccountIdSignal.set(null);
    this.twoFactorPending.set(false);
  }

  /** Every auth endpoint is the same cookie-session call with the same CSRF/JSON headers. */
  private send(path: string, body?: unknown, method = 'POST'): Promise<Response> {
    return fetch(`${this.apiOrigin()}${path}`, {
      method,
      credentials: 'include',
      headers: this.jsonHeaders(),
      ...(body === undefined ? {} : { body: JSON.stringify(body) }),
    });
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
