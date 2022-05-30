import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TodoOvertimePage } from './todo-overtime.page';

describe('TodoOvertimePage', () => {
  let component: TodoOvertimePage;
  let fixture: ComponentFixture<TodoOvertimePage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TodoOvertimePage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TodoOvertimePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
