import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { LeavesPage } from './leaves.page';

describe('LeavesPage', () => {
  let component: LeavesPage;
  let fixture: ComponentFixture<LeavesPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ LeavesPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(LeavesPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
