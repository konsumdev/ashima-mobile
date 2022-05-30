import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ClockinPage } from './clockin.page';

describe('ClockinPage', () => {
  let component: ClockinPage;
  let fixture: ComponentFixture<ClockinPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ClockinPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ClockinPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
