import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { TodoTimesheetsPage } from './todo-timesheets.page';

const routes: Routes = [
  {
    path: '',
    component: TodoTimesheetsPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [TodoTimesheetsPage]
})
export class TodoTimesheetsPageModule {}
