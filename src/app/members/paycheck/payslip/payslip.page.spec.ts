import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PayslipPage } from './payslip.page';

describe('PayslipPage', () => {
  let component: PayslipPage;
  let fixture: ComponentFixture<PayslipPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PayslipPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PayslipPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
