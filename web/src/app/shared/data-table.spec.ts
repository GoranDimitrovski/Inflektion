import { Component } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { DataTable } from './data-table';

@Component({
  imports: [DataTable],
  template: `
    <app-data-table [loading]="loading" [error]="error" [empty]="empty" emptyMessage="Nothing here.">
      <p class="rows">actual rows</p>
    </app-data-table>
  `,
})
class HostComponent {
  loading = false;
  error: string | null = null;
  empty = false;
}

describe('DataTable', () => {
  let fixture: ComponentFixture<HostComponent>;

  beforeEach(() => {
    TestBed.configureTestingModule({ imports: [HostComponent] });
    fixture = TestBed.createComponent(HostComponent);
  });

  it('shows a loading state', () => {
    fixture.componentInstance.loading = true;
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Loading');
    expect(fixture.nativeElement.querySelector('.rows')).toBeNull();
  });

  it('shows an error state', () => {
    fixture.componentInstance.error = 'Something broke.';
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Something broke.');
    expect(fixture.nativeElement.querySelector('.rows')).toBeNull();
  });

  it('shows the empty message when there are no rows', () => {
    fixture.componentInstance.empty = true;
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Nothing here.');
    expect(fixture.nativeElement.querySelector('.rows')).toBeNull();
  });

  it('projects the caller-supplied rows otherwise', () => {
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.rows')).not.toBeNull();
  });
});
