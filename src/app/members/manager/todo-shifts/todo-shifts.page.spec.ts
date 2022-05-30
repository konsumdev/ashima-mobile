import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TodoShiftsPage } from './todo-shifts.page';

describe('TodoShiftsPage', () => {
  let component: TodoShiftsPage;
  let fixture: ComponentFixture<TodoShiftsPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TodoShiftsPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TodoShiftsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
