import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ManagerUndertimePage } from './manager-undertime.page';

describe('ManagerUndertimePage', () => {
  let component: ManagerUndertimePage;
  let fixture: ComponentFixture<ManagerUndertimePage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ManagerUndertimePage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ManagerUndertimePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
