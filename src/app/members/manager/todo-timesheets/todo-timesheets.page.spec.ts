import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TodoTimesheetsPage } from './todo-timesheets.page';

describe('TodoTimesheetsPage', () => {
  let component: TodoTimesheetsPage;
  let fixture: ComponentFixture<TodoTimesheetsPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TodoTimesheetsPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TodoTimesheetsPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
