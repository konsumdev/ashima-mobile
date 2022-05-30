import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ContributionsPage } from './contributions.page';

describe('ContributionsPage', () => {
  let component: ContributionsPage;
  let fixture: ComponentFixture<ContributionsPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ContributionsPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ContributionsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
