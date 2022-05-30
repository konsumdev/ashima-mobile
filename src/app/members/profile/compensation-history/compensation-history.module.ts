import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { CompensationHistoryPage } from './compensation-history.page';

const routes: Routes = [
  {
    path: '',
    component: CompensationHistoryPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [CompensationHistoryPage]
})
export class CompensationHistoryPageModule {}
