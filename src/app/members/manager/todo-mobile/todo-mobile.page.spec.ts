import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TodoMobilePage } from './todo-mobile.page';

describe('TodoMobilePage', () => {
  let component: TodoMobilePage;
  let fixture: ComponentFixture<TodoMobilePage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TodoMobilePage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TodoMobilePage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
