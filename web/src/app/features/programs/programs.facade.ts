import { Injectable, signal } from '@angular/core';
import { v1ProgramsIndex, v1ProgramsStore } from '../../api/sdk.gen';
import type { V1ProgramsIndexResponses, V1ProgramsStoreData } from '../../api/types.gen';
import { firstApiError } from '../../shared/api-error';

/** A single `programs` resource object, JSON:API-shaped (`data.attributes.*`). */
export type ProgramResource = V1ProgramsIndexResponses[200]['data'][number];

/** The `data.attributes` payload needed to create a program. */
export type CreateProgramAttributes = V1ProgramsStoreData['body']['data']['attributes'];

/** No NgRx / global store per project rules — this is local state for the `features/programs` route only. */
@Injectable()
export class ProgramsFacade {
  readonly programs = signal<ProgramResource[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  async load(accountId: number): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    const { data, error } = await v1ProgramsIndex({
      path: { account: String(accountId) },
    });

    if (error) {
      this.error.set(firstApiError(error, 'Failed to load programs.'));
    } else if (data) {
      this.programs.set(data.data);
    }

    this.loading.set(false);
  }

  async create(accountId: number, attributes: CreateProgramAttributes): Promise<boolean> {
    this.error.set(null);

    const { data, error } = await v1ProgramsStore({
      path: { account: String(accountId) },
      body: { data: { type: 'programs', attributes } },
    });

    if (error) {
      this.error.set(firstApiError(error, 'Failed to create program.'));

      return false;
    }

    if (data) {
      this.programs.update((programs) => [data.data, ...programs]);
    }

    return true;
  }
}
