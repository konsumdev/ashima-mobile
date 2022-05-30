import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ChangeLogsPage } from './change-logs.page';

describe('ChangeLogsPage', () => {
  let component: ChangeLogsPage;
  let fixture: ComponentFixture<ChangeLogsPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ChangeLogsPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ChangeLogsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
