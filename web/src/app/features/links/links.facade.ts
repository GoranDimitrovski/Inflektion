import { Injectable, signal } from '@angular/core';
import { v1LinksIndex, v1LinksStore, v1LinksUpdate, v1ProgramsIndex } from '../../api/sdk.gen';
import type { V1LinksIndexResponses, V1LinksStoreData } from '../../api/types.gen';
import { ProgramResource } from '../programs/programs.facade';
import { firstApiError } from '../../shared/api-error';

export type LinkResource = V1LinksIndexResponses[200]['data'][number];
export type CreateLinkAttributes = V1LinksStoreData['body']['data']['attributes'];


@Injectable()
export class LinksFacade {
  readonly programs = signal<ProgramResource[]>([]);
  readonly links = signal<LinkResource[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  async loadPrograms(accountId: number): Promise<void> {
    const { data } = await v1ProgramsIndex({ path: { account: String(accountId) } });

    this.programs.set(data?.data ?? []);
  }

  async loadLinks(accountId: number, programId: number): Promise<void> {
    this.loading.set(true);
    this.error.set(null);

    const { data, error } = await v1LinksIndex({
      path: { account: String(accountId) },
      query: { filter: { programId: String(programId) } },
    } as never);

    if (error) {
      this.error.set(firstApiError(error, 'Failed to load links.'));
    } else {
      this.links.set(data?.data ?? []);
    }

    this.loading.set(false);
  }

  async create(accountId: number, attributes: CreateLinkAttributes): Promise<string | null> {
    const { data, error } = await v1LinksStore({
      path: { account: String(accountId) },
      body: { data: { type: 'links', attributes } },
    });

    if (error) {
      return firstApiError(error, 'Failed to create the link.');
    }

    if (data) {
      this.links.update((links) => [data.data, ...links]);
    }

    return null;
  }

  async updateStatus(accountId: number, linkId: string, status: string): Promise<void> {
    const { data, error } = await v1LinksUpdate({
      path: { account: String(accountId), link: linkId },
      body: { data: { type: 'links', id: linkId, attributes: { status } } },
    });

    if (!error && data) {
      this.links.update((links) => links.map((link) => (link.id === linkId ? data.data : link)));
    }
  }
}
