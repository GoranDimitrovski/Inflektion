import { ChangeDetectionStrategy, Component, OnInit, signal, inject } from '@angular/core';
import { FormField, form, required, submit } from '@angular/forms/signals';
import { Modal } from '../../shared/modal';
import { ProgramsFacade } from './programs.facade';

interface CreateProgramModel {
  name: string;
  slug: string;
}

@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [FormField, Modal],
  providers: [ProgramsFacade],
  selector: 'app-programs-page',
  styleUrl: './programs-page.scss',
  templateUrl: './programs-page.html',
})
export class ProgramsPage implements OnInit {
  protected readonly facade = inject(ProgramsFacade);
  protected readonly showCreateModal = signal(false);

  protected readonly model = signal<CreateProgramModel>({ name: '', slug: '' });

  protected readonly createForm = form(this.model, (path) => {
    required(path.name, { message: 'Name is required.' });
    required(path.slug, { message: 'Slug is required.' });
  });

  protected readonly submitting = signal(false);

  ngOnInit(): void {
    void this.facade.load();
  }

  protected async onSubmit(): Promise<void> {
    this.submitting.set(true);

    await submit(this.createForm, async () => {
      const created = await this.facade.create(this.model());

      if (created) {
        this.model.set({ name: '', slug: '' });
        this.showCreateModal.set(false);
      }
    });

    this.submitting.set(false);
  }

}
