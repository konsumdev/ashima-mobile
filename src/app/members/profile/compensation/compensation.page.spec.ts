import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CompensationPage } from './compensation.page';

describe('CompensationPage', () => {
  let component: CompensationPage;
  let fixture: ComponentFixture<CompensationPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CompensationPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CompensationPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
