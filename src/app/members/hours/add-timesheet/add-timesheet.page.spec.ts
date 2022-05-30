import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AddTimesheetPage } from './add-timesheet.page';

describe('AddTimesheetPage', () => {
  let component: AddTimesheetPage;
  let fixture: ComponentFixture<AddTimesheetPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AddTimesheetPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AddTimesheetPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
