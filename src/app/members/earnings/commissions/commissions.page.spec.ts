import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CommissionsPage } from './commissions.page';

describe('CommissionsPage', () => {
  let component: CommissionsPage;
  let fixture: ComponentFixture<CommissionsPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CommissionsPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CommissionsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
