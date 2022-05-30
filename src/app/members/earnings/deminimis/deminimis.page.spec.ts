import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { DeminimisPage } from './deminimis.page';

describe('DeminimisPage', () => {
  let component: DeminimisPage;
  let fixture: ComponentFixture<DeminimisPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ DeminimisPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(DeminimisPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
