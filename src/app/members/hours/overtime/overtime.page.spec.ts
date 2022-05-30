import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { OvertimePage } from './overtime.page';

describe('OvertimePage', () => {
  let component: OvertimePage;
  let fixture: ComponentFixture<OvertimePage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ OvertimePage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(OvertimePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
