import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ApplyPage } from './apply.page';

describe('ApplyPage', () => {
  let component: ApplyPage;
  let fixture: ComponentFixture<ApplyPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ApplyPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ApplyPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
