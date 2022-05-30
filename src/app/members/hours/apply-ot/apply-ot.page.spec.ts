import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ApplyOtPage } from './apply-ot.page';

describe('ApplyOtPage', () => {
  let component: ApplyOtPage;
  let fixture: ComponentFixture<ApplyOtPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ApplyOtPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ApplyOtPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
