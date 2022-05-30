import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ManagerTimesheetsPage } from './manager-timesheets.page';

describe('ManagerTimesheetsPage', () => {
  let component: ManagerTimesheetsPage;
  let fixture: ComponentFixture<ManagerTimesheetsPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ManagerTimesheetsPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ManagerTimesheetsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
