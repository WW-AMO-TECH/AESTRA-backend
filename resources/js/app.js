import './bootstrap';
import express from 'express';
import cors from 'cors';
const app = express();

// app.use(cors({
//   origin: 'https://aestra-frontend.vercel.app'
// }));

app.use(cors({
  origin: 'https://aestra-frontend.vercel.app',
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
  credentials: true
}));