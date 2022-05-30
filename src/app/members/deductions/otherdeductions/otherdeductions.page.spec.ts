import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { OtherdeductionsPage } from './otherdeductions.page';

describe('OtherdeductionsPage', () => {
  let component: OtherdeductionsPage;
  let fixture: ComponentFixture<OtherdeductionsPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ OtherdeductionsPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(OtherdeductionsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
