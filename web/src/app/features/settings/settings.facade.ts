import { Injectable, signal } from '@angular/core';
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
import { firstApiError } from '../../shared/api-error';

export type ApiTokenResource = V1ApiTokensIndexResponses[200]['data'][number];

const JSON_API_HEADERS = { 'Content-Type': 'application/vnd.api+json' };

function firstFieldError(error: unknown, fallback: string): string {
  const message = (error as { message?: string } | undefined)?.message;

  return message ?? fallback;
}

@Injectable()
export class SettingsFacade {
  readonly apiTokens = signal<ApiTokenResource[]>([]);
  readonly loadingTokens = signal(false);
  readonly tokensError = signal<string | null>(null);

  readonly qrCodeSvg = signal<string | null>(null);
  readonly secretKey = signal<string | null>(null);
  readonly recoveryCodes = signal<string[]>([]);
  readonly twoFactorError = signal<string | null>(null);

  async loadApiTokens(accountId: number): Promise<void> {
    this.loadingTokens.set(true);
    this.tokensError.set(null);

    const { data, error } = await v1ApiTokensIndex({ path: { account: String(accountId) } });

    if (error) {
      this.tokensError.set(firstApiError(error, 'Failed to load API tokens.'));
    } else {
      this.apiTokens.set(data?.data ?? []);
    }

    this.loadingTokens.set(false);
  }

  async createApiToken(
    accountId: number,
    name: string,
    abilities: string[],
  ): Promise<{ token: ApiTokenResource; plainTextToken: string } | { error: string }> {
    const { data, error } = await v1ApiTokensStore({
      path: { account: String(accountId) },
      body: { data: { type: 'api-tokens', attributes: { name, abilities } } },
      headers: JSON_API_HEADERS,
    });

    if (error || !data) {
      return { error: firstApiError(error, 'Failed to create the API token.') };
    }

    this.apiTokens.update((tokens) => [data.data, ...tokens]);

    return { token: data.data, plainTextToken: data.meta.plainTextToken };
  }

  async revokeApiToken(accountId: number, tokenId: string): Promise<boolean> {
    const { error } = await v1ApiTokensDestroy({
      path: { account: String(accountId), api_token: tokenId },
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
      this.twoFactorError.set(firstFieldError(error, 'Failed to start two-factor setup.'));

      return false;
    }

    const [qr, secret] = await Promise.all([twoFactorQrCode(), twoFactorSecretKey()]);

    this.qrCodeSvg.set(qr.data?.data.svg ?? null);
    this.secretKey.set((secret.data?.data.secretKey as string | undefined) ?? null);

    return true;
  }

  async confirmEnrollment(code: string): Promise<boolean> {
    this.twoFactorError.set(null);

    const { error } = await twoFactorConfirm({ body: { code } as never });

    if (error) {
      this.twoFactorError.set(firstFieldError(error, 'That code is invalid or has expired.'));

      return false;
    }

    this.qrCodeSvg.set(null);
    this.secretKey.set(null);

    const { data } = await twoFactorRecoveryCodesIndex();
    this.recoveryCodes.set((data?.data as string[] | undefined) ?? []);

    return true;
  }

  async viewRecoveryCodes(): Promise<void> {
    const { data } = await twoFactorRecoveryCodesIndex();

    this.recoveryCodes.set((data?.data as string[] | undefined) ?? []);
  }

  async regenerateRecoveryCodes(): Promise<void> {
    const { data } = await twoFactorRecoveryCodesStore();

    this.recoveryCodes.set((data?.data as string[] | undefined) ?? []);
  }

  async disableTwoFactor(): Promise<string | null> {
    this.twoFactorError.set(null);

    const { error } = await twoFactorDisable();

    if (error) {
      const message = firstFieldError(error, 'Failed to disable two-factor authentication.');
      this.twoFactorError.set(message);

      return message;
    }

    this.recoveryCodes.set([]);

    return null;
  }
}
