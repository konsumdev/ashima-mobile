import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TodoLeavesPage } from './todo-leaves.page';

describe('TodoLeavesPage', () => {
  let component: TodoLeavesPage;
  let fixture: ComponentFixture<TodoLeavesPage>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TodoLeavesPage ],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TodoLeavesPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
