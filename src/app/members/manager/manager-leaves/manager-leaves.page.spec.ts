import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ManagerLeavesPage } from './manager-leaves.page';

describe('ManagerLeavesPage', () => {
  let component: ManagerLeavesPage;
  let fixture: ComponentFixture<ManagerLeavesPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ManagerLeavesPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ManagerLeavesPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
