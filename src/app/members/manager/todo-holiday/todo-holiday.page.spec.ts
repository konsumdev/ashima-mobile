import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TodoHolidayPage } from './todo-holiday.page';

describe('TodoHolidayPage', () => {
  let component: TodoHolidayPage;
  let fixture: ComponentFixture<TodoHolidayPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TodoHolidayPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TodoHolidayPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
