import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { MissPunchPage } from './miss-punch.page';

describe('MissPunchPage', () => {
  let component: MissPunchPage;
  let fixture: ComponentFixture<MissPunchPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ MissPunchPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(MissPunchPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
