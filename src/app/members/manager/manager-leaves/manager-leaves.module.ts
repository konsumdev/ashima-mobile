import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { ManagerLeavesPage } from './manager-leaves.page';

const routes: Routes = [
  {
    path: '',
    component: ManagerLeavesPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [ManagerLeavesPage]
})
export class ManagerLeavesPageModule {}
