import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AllowancesPage } from './allowances.page';

describe('AllowancesPage', () => {
  let component: AllowancesPage;
  let fixture: ComponentFixture<AllowancesPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AllowancesPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AllowancesPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
