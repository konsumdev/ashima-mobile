import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { WithholdingtaxPage } from './withholdingtax.page';

const routes: Routes = [
  {
    path: '',
    component: WithholdingtaxPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [WithholdingtaxPage]
})
export class WithholdingtaxPageModule {}
