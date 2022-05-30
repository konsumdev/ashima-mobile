import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TodoRestdayPage } from './todo-restday.page';

describe('TodoRestdayPage', () => {
  let component: TodoRestdayPage;
  let fixture: ComponentFixture<TodoRestdayPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TodoRestdayPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TodoRestdayPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
