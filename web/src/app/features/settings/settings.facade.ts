import { inject, Injectable, signal } from '@angular/core';
import {
  twoFactorConfirm,
  twoFactorDisable,
  twoFactorEnable,
  twoFactorQrCode,
  twoFactorRecoveryCodesIndex,
  twoFactorRecoveryCodesStore,
  twoFactorSecretKey,
  v1ApiTokensDestroy,
  v1ApiTokensIndex,
  v1ApiTokensStore,
} from '../../api/sdk.gen';
import type { V1ApiTokensIndexResponses } from '../../api/types.gen';
import { SessionService } from '../../core/session';
import { firstApiError } from '../../shared/api-error';

export type ApiTokenResource = V1ApiTokensIndexResponses[200]['data'][number];

@Injectable()
export class SettingsFacade {
  readonly apiTokens = signal<ApiTokenResource[]>([]);
  readonly loadingTokens = signal(false);
  readonly tokensError = signal<string | null>(null);

  readonly qrCodeSvg = signal<string | null>(null);
  readonly secretKey = signal<string | null>(null);
  readonly recoveryCodes = signal<string[]>([]);
  readonly twoFactorError = signal<string | null>(null);

  private readonly session = inject(SessionService);

  /** Route-scoped: the `accounts/:accountId` guard has already resolved the account these calls belong to. */
  private account(): string {
    return String(this.session.requireAccountId());
  }

  async loadApiTokens(): Promise<void> {
    this.loadingTokens.set(true);
    this.tokensError.set(null);

    const { data, error } = await v1ApiTokensIndex({ path: { account: this.account() } });

    if (error) {
      this.tokensError.set(firstApiError(error, 'Failed to load API tokens.'));
    } else {
      this.apiTokens.set(data?.data ?? []);
    }

    this.loadingTokens.set(false);
  }

  async createApiToken(
    name: string,
    abilities: string[],
  ): Promise<{ token: ApiTokenResource; plainTextToken: string } | { error: string }> {
    const { data, error } = await v1ApiTokensStore({
      path: { account: this.account() },
      body: { data: { type: 'api-tokens', attributes: { name, abilities } } },
    });

    if (error || !data) {
      return { error: firstApiError(error, 'Failed to create the API token.') };
    }

    this.apiTokens.update((tokens) => [data.data, ...tokens]);

    return { token: data.data, plainTextToken: data.meta.plainTextToken };
  }

  async revokeApiToken(tokenId: string): Promise<boolean> {
    const { error } = await v1ApiTokensDestroy({
      path: { account: this.account(), api_token: tokenId },
    });

    if (error) {
      return false;
    }

    this.apiTokens.update((tokens) => tokens.filter((token) => token.id !== tokenId));

    return true;
  }

  /** Starts enrollment: generates a secret + recovery codes (unconfirmed) and fetches the QR code to scan. */
  async startEnrollment(): Promise<boolean> {
    this.twoFactorError.set(null);

    const { error } = await twoFactorEnable();

    if (error) {
      this.twoFactorError.set(firstApiError(error, 'Failed to start two-factor setup.'));

      return false;
    }

    const [qr, secret] = await Promise.all([twoFactorQrCode(), twoFactorSecretKey()]);

    this.qrCodeSvg.set(qr.data?.data.svg ?? null);
    this.secretKey.set(secret.data?.data.secretKey ?? null);

    return true;
  }

  async confirmEnrollment(code: string): Promise<boolean> {
    this.twoFactorError.set(null);

    const { error } = await twoFactorConfirm({ body: { code } });

    if (error) {
      this.twoFactorError.set(firstApiError(error, 'That code is invalid or has expired.'));

      return false;
    }

    this.qrCodeSvg.set(null);
    this.secretKey.set(null);

    const { data } = await twoFactorRecoveryCodesIndex();
    this.recoveryCodes.set(data?.data ?? []);

    return true;
  }

  async viewRecoveryCodes(): Promise<void> {
    const { data } = await twoFactorRecoveryCodesIndex();

    this.recoveryCodes.set(data?.data ?? []);
  }

  async regenerateRecoveryCodes(): Promise<void> {
    const { data } = await twoFactorRecoveryCodesStore();

    this.recoveryCodes.set(data?.data ?? []);
  }

  async disableTwoFactor(): Promise<string | null> {
    this.twoFactorError.set(null);

    const { error } = await twoFactorDisable();

    if (error) {
      const message = firstApiError(error, 'Failed to disable two-factor authentication.');
      this.twoFactorError.set(message);

      return message;
    }

    this.recoveryCodes.set([]);

    return null;
  }
}
