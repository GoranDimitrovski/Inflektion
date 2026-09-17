import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, OnInit, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { SessionService } from '../../core/session';
import { DataTable } from '../../shared/data-table';
import { Menu } from '../../shared/menu';
import { Modal } from '../../shared/modal';
import { LinkResource, LinksFacade } from './links.facade';

interface CreateLinkModel {
  destinationUrl: string;
  personalizationStrategy: string;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [DataTable, DatePipe, FormField, Menu, Modal],
  providers: [LinksFacade],
  selector: 'app-links-page',
  styleUrl: './links-page.scss',
  templateUrl: './links-page.html',
})
export class LinksPage implements OnInit {
  protected readonly facade = inject(LinksFacade);
  protected readonly session = inject(SessionService);

  protected readonly selectedProgramId = signal<number | null>(null);
  protected readonly createError = signal<string | null>(null);
  protected readonly submitting = signal(false);
  protected readonly showCreateModal = signal(false);

  protected readonly model = signal<CreateLinkModel>({ destinationUrl: '', personalizationStrategy: '' });
  protected readonly createForm = form(this.model, (path) => {
    required(path.destinationUrl, { message: 'Destination URL is required.' });
  });

  async ngOnInit(): Promise<void> {
    await this.facade.loadPrograms(this.accountId());

    const firstProgramId = this.facade.programs()[0]?.id;

    if (firstProgramId) {
      await this.onSelectProgram(Number(firstProgramId));
    }
  }

  protected async onSelectProgram(programId: number | string): Promise<void> {
    const id = Number(programId);
    this.selectedProgramId.set(id);
    await this.facade.loadLinks(this.accountId(), id);
  }

  protected async onSubmit(): Promise<void> {
    const programId = this.selectedProgramId();

    if (programId === null) {
      return;
    }

    this.submitting.set(true);
    this.createError.set(null);

    await submit(this.createForm, async () => {
      const { destinationUrl, personalizationStrategy } = this.model();

      const error = await this.facade.create(this.accountId(), {
        programId,
        destinationUrl,
        ...(personalizationStrategy ? { personalizationStrategy } : {}),
      });

      if (error) {
        this.createError.set(error);
      } else {
        this.model.set({ destinationUrl: '', personalizationStrategy: '' });
        this.showCreateModal.set(false);
      }
    });

    this.submitting.set(false);
  }

  protected async onToggleStatus(link: LinkResource): Promise<void> {
    const nextStatus = link.attributes.status === 'active' ? 'paused' : 'active';

    await this.facade.updateStatus(this.accountId(), link.id, nextStatus);
  }

  protected async copyRedirectUrl(url: string): Promise<void> {
    try {
      await navigator.clipboard.writeText(url);
    } catch {
      // Clipboard access can be denied by the browser — nothing to recover, the URL is still visible to copy manually.
    }
  }

  private accountId(): number {
    const accountId = this.session.activeMembership()?.account.id;

    if (accountId === undefined) {
      throw new Error('LinksPage rendered without an active account — the route guard should prevent this.');
    }

    return accountId;
  }
}
