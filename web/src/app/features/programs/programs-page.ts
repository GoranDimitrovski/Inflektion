import { ChangeDetectionStrategy, Component, OnInit, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { SessionService } from '../../core/session';
import { ProgramsFacade } from './programs.facade';

interface CreateProgramModel {
  name: string;
  slug: string;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormField],
  providers: [ProgramsFacade],
  selector: 'app-programs-page',
  styleUrl: './programs-page.scss',
  templateUrl: './programs-page.html',
})
export class ProgramsPage implements OnInit {
  protected readonly facade = inject(ProgramsFacade);
  private readonly session = inject(SessionService);

  protected readonly model = signal<CreateProgramModel>({ name: '', slug: '' });

  protected readonly createForm = form(this.model, (path) => {
    required(path.name, { message: 'Name is required.' });
    required(path.slug, { message: 'Slug is required.' });
  });

  protected readonly submitting = signal(false);

  ngOnInit(): void {
    // The `accounts/:accountId` route guard has already resolved the
    // active membership before this component can render.
    void this.facade.load(this.accountId());
  }

  protected async onSubmit(): Promise<void> {
    this.submitting.set(true);

    await submit(this.createForm, async () => {
      const created = await this.facade.create(this.accountId(), this.model());

      if (created) {
        this.model.set({ name: '', slug: '' });
      }
    });

    this.submitting.set(false);
  }

  private accountId(): number {
    const accountId = this.session.activeMembership()?.account.id;

    if (accountId === undefined) {
      throw new Error(
        'ProgramsPage rendered without an active account — the route guard should prevent this.',
      );
    }

    return accountId;
  }
}
