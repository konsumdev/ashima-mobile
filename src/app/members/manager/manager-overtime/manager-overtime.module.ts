import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { ManagerOvertimePage } from './manager-overtime.page';

const routes: Routes = [
  {
    path: '',
    component: ManagerOvertimePage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [ManagerOvertimePage]
})
export class ManagerOvertimePageModule {}
