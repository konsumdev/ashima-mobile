import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ManagerOvertimePage } from './manager-overtime.page';

describe('ManagerOvertimePage', () => {
  let component: ManagerOvertimePage;
  let fixture: ComponentFixture<ManagerOvertimePage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ManagerOvertimePage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ManagerOvertimePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
