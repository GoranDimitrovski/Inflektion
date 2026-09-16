import { Injectable, signal } from '@angular/core';
import { v1ProgramsIndex, v1ProgramsStore } from '../../api/sdk.gen';
import type { V1ProgramsIndexResponses, V1ProgramsStoreData } from '../../api/types.gen';

/** A single `programs` resource object, JSON:API-shaped (`data.attributes.*`). */
export type ProgramResource = V1ProgramsIndexResponses[200]['data'][number];

/** The `data.attributes` payload needed to create a program. */
export type CreateProgramAttributes = V1ProgramsStoreData['body']['data']['attributes'];

/**
 * Route-scoped facade wrapping the generated API client with signals.
 * No NgRx / global store per project rules — this is local state for the
 * `features/programs` route only.
 */
@Injectable()
export class ProgramsFacade {
  readonly programs = signal<ProgramResource[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  async load(): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    const { data, error } = await v1ProgramsIndex();

    if (error) {
      this.error.set('Failed to load programs.');
    } else if (data) {
      this.programs.set(data.data);
    }

    this.loading.set(false);
  }

  async create(attributes: CreateProgramAttributes): Promise<boolean> {
    this.error.set(null);

    const { data, error } = await v1ProgramsStore({
      body: { data: { type: 'programs', attributes } },
      // The generated SDK hardcodes "Content-Type: application/json" on
      // every store call; JSON:API requires the vendor media type instead.
      headers: { 'Content-Type': 'application/vnd.api+json' },
    });

    if (error) {
      this.error.set('Failed to create program.');

      return false;
    }

    if (data) {
      this.programs.update((programs) => [data.data, ...programs]);
    }

    return true;
  }
}
