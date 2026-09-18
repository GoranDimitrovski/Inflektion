import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { SessionService } from '../../core/session';
import { DataTable } from '../../shared/data-table';
import { Modal } from '../../shared/modal';
import { ApiTokenResource, SettingsFacade } from './settings.facade';

interface TokenModel {
  name: string;
}

interface ChallengeModel {
  code: string;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [DataTable, DatePipe, FormField, Modal],
  providers: [SettingsFacade],
  selector: 'app-settings-page',
  styleUrl: './settings-page.scss',
  templateUrl: './settings-page.html',
})
export class SettingsPage implements OnInit {
  protected readonly facade = inject(SettingsFacade);
  protected readonly session = inject(SessionService);

  protected readonly tab = signal<'security' | 'tokens'>('security');

  protected readonly showCreateTokenModal = signal(false);
  protected readonly enrolling = signal(false);
  protected readonly justCreatedToken = signal<{ token: ApiTokenResource; plainTextToken: string } | null>(null);
  protected readonly selectedAbilities = signal<Record<string, boolean>>({});
  protected readonly disableError = signal<string | null>(null);

  protected readonly tokenModel = signal<TokenModel>({ name: '' });
  protected readonly tokenForm = form(this.tokenModel, (path) => {
    required(path.name, { message: 'Name is required.' });
  });
  protected readonly creatingToken = signal(false);
  protected readonly tokenCreateError = signal<string | null>(null);

  protected readonly challengeModel = signal<ChallengeModel>({ code: '' });
  protected readonly challengeForm = form(this.challengeModel, (path) => {
    required(path.code, { message: 'Enter the code from your authenticator app.' });
  });
  protected readonly confirming = signal(false);

  ngOnInit(): void {
    // Any member can view/create/revoke their own API tokens (ApiTokenPolicy
    // allows all of viewAny/create; delete is scoped server-side to tokens
    // the caller owns), so this isn't gated behind a permission check.
    void this.facade.loadApiTokens(this.session.requireAccountId());
  }

  /** An issued token's abilities are capped to the issuer's own role permissions — see IssueApiToken. */
  protected availableAbilities(): string[] {
    return this.session.permissions();
  }

  protected qrCodeDataUri(): string | null {
    const svg = this.facade.qrCodeSvg();

    return svg ? `data:image/svg+xml;utf8,${encodeURIComponent(svg)}` : null;
  }

  protected async onStartEnrollment(): Promise<void> {
    this.enrolling.set(true);

    if (!(await this.facade.startEnrollment())) {
      this.enrolling.set(false);
    }
  }

  protected async onConfirmEnrollment(): Promise<void> {
    this.confirming.set(true);

    await submit(this.challengeForm, async () => {
      const confirmed = await this.facade.confirmEnrollment(this.challengeModel().code);

      if (confirmed) {
        this.enrolling.set(false);
        this.challengeModel.set({ code: '' });
        await this.session.load();
      }
    });

    this.confirming.set(false);
  }

  protected async onDisable(): Promise<void> {
    this.disableError.set(await this.facade.disableTwoFactor());
    await this.session.load();
  }

  protected toggleAbility(ability: string, checked: boolean): void {
    this.selectedAbilities.update((current) => ({ ...current, [ability]: checked }));
  }

  protected async onCreateToken(): Promise<void> {
    this.creatingToken.set(true);
    this.tokenCreateError.set(null);
    this.justCreatedToken.set(null);

    await submit(this.tokenForm, async () => {
      const abilities = this.availableAbilities().filter((ability) => this.selectedAbilities()[ability]);
      const result = await this.facade.createApiToken(this.session.requireAccountId(), this.tokenModel().name, abilities);

      if ('error' in result) {
        this.tokenCreateError.set(result.error);

        return;
      }

      this.justCreatedToken.set(result);
      this.tokenModel.set({ name: '' });
      this.selectedAbilities.set({});
      this.showCreateTokenModal.set(false);
    });

    this.creatingToken.set(false);
  }

  protected async onRevokeToken(token: ApiTokenResource): Promise<void> {
    await this.facade.revokeApiToken(this.session.requireAccountId(), token.id);
  }

}
