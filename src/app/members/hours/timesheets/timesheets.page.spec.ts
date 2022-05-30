import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TimesheetsPage } from './timesheets.page';

describe('TimesheetsPage', () => {
  let component: TimesheetsPage;
  let fixture: ComponentFixture<TimesheetsPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TimesheetsPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TimesheetsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
