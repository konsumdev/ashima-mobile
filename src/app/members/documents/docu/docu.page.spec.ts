import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { DocuPage } from './docu.page';

describe('DocuPage', () => {
  let component: DocuPage;
  let fixture: ComponentFixture<DocuPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ DocuPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(DocuPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
