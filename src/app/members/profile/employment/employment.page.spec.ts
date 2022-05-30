import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EmploymentPage } from './employment.page';

describe('EmploymentPage', () => {
  let component: EmploymentPage;
  let fixture: ComponentFixture<EmploymentPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ EmploymentPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EmploymentPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
