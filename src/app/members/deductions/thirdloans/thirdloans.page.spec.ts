import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ThirdloansPage } from './thirdloans.page';

describe('ThirdloansPage', () => {
  let component: ThirdloansPage;
  let fixture: ComponentFixture<ThirdloansPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ThirdloansPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ThirdloansPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
