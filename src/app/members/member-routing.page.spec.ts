import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { MemberRoutingPage } from './member-routing.page';

describe('MemberRoutingPage', () => {
  let component: MemberRoutingPage;
  let fixture: ComponentFixture<MemberRoutingPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ MemberRoutingPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(MemberRoutingPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
