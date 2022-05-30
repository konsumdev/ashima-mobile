import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { WithholdingtaxPage } from './withholdingtax.page';

describe('WithholdingtaxPage', () => {
  let component: WithholdingtaxPage;
  let fixture: ComponentFixture<WithholdingtaxPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ WithholdingtaxPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(WithholdingtaxPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
