import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { ManagerUndertimePage } from './manager-undertime.page';

const routes: Routes = [
  {
    path: '',
    component: ManagerUndertimePage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [ManagerUndertimePage]
})
export class ManagerUndertimePageModule {}
