import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ShiftPage } from './shift.page';

describe('ShiftPage', () => {
  let component: ShiftPage;
  let fixture: ComponentFixture<ShiftPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ShiftPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ShiftPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
